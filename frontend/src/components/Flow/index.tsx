import React, { useState, useEffect, useCallback, useRef } from 'react';
import ReactFlow, { useNodesState, useEdgesState, addEdge, MiniMap, Controls, ReactFlowProps, Background, ReactFlowActions, ReactFlowRefType, ReactFlowInstance, ReactFlowState, NodeDragHandler, Node } from 'reactflow';
import styled, { ThemeProvider } from 'styled-components';
import { darkTheme, lightTheme } from './Theme';
import useSWR from 'swr'

import 'reactflow/dist/style.css';
import { useTheme } from 'next-themes';
import Drag from './Drag';
import RackNode from './RackNode';
import LabelNode from './LabelNode';
import { DeleteModal } from '../DeleteModal';
import { fetcher } from '@/app/utils/Fetcher';

interface ControlsProps {
    theme: Theme
}
interface Theme {
    controlsBg: string;
    controlsColor: string;
    controlsBorder: string;
    controlsBgHover: string;
}
const ControlsStyled = styled(Controls)`
  button {
    background-color: ${(props: ControlsProps) => props.theme.controlsBg};
    color: ${(props: ControlsProps) => props.theme.controlsColor};
    border-bottom: 1px solid ${(props: ControlsProps) => props.theme.controlsBorder};

    &:hover {
      background-color: ${(props: ControlsProps) => props.theme.controlsBgHover};
    }

    path {
      fill: currentColor;
    }
  }
`;

const nodeTypes = {
    rackNode: RackNode,
    labelNode: LabelNode,
};
export interface NewNode {
    id: string;
    type: string;
    position: { x: number; y: number };
    data: { label: string; tor?: string; ts?: string; onChange: (event: any, id: string) => void; delete: (node: NewNode) => void };
}
const Flow = () => {
    const { resolvedTheme } = useTheme()
    const flowTheme = resolvedTheme === 'light' ? lightTheme : darkTheme;
    const [nodes, setNodes, onNodesChange] = useNodesState([]);
    const [edges, setEdges, onEdgesChange] = useEdgesState([]);

    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleteNodeObj, setDeleteNodeObj] = useState<Node>();

    const [editRackOpen, setEditRackOpen] = useState(false);
    const [editRackObj, setEditRackObj] = useState<Node>();

    const [reactFlowInstance, setReactFlowInstance] = useState<ReactFlowInstance>();
    const reactFlowWrapper = useRef<ReactFlowRefType>(null);

    const onNodeDragStop = (event: React.MouseEvent, node: Node) => {
        const requestOptions = {
            method: 'PATCH',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ x: node.position.x, y: node.position.y })
        };
        fetch('/api/node/' + node.id, requestOptions)

    }

    const { data } = useSWR('/api/node', fetcher, { suspense: true })

    interface ServerNode {
        id: number;
        x: number;
        y: number;
        created_at: string;
        updated_at: string;
        rack?: Rack;
        label?: Label;
    }
    interface Rack {
        label: string;
        tor_id?: number;
        ts_id?: number;
        created_at: string;
        updated_at: string;
    }
    interface Label {
        label: string;
        created_at: string;
        updated_at: string;
    }

    useEffect(() => {
        if (data?.status) {
            setNodes(
                data.json.map((node: ServerNode) => {
                    return {
                        id: (node.id).toString(),
                        type: node.rack ? 'rackNode' : 'labelNode',
                        position: { x: node.x, y: node.y },
                        data: {
                            label: node.rack ? node.rack.label : node.label?.label,
                            tor: node.rack?.tor_id ? node.rack?.tor_id : '',
                            ts: node.rack?.ts_id ? node.rack?.ts_id : '',
                            onChange: onChange,
                            delete: deleteNode,
                        },
                    };
                })
            );
        }
    }, [data])

    const onDragOver = useCallback((event: any) => {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
    }, []);

    interface NodeEvent {
        label: string;
        tor?: any;
        ts?: any;
        onChange: (event: any, id: any) => void;
        delete: (id: any) => void;
    }
    const onChange = async (event: NodeEvent, id: string) => {
        const requestOptions = {
            method: 'PATCH',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({
                label: event.label,
                ...(event.tor ? { tor_id: parseInt(event.tor) } : {}),
                ...(event.ts ? { ts_id: parseInt(event.ts) } : {})
            })
        };
        const response = await fetch('/api/node/' + id, requestOptions)
        if (response.ok) {
            setNodes((nds) =>
                nds.map((node) => {
                    if (node.id !== id) {
                        return node;
                    }
                    return {
                        ...node,
                        data: {
                            ...node.data,
                            ...event,
                        },
                    };
                })
            );
            return true
        } else {
            return false
        }
    };

    const deleteNode = (node: NewNode) => {
        setDeleteNodeObj(node)
        setDeleteOpen(true)
    };

    const deleteRequest = () => {
        const requestOptions = {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
        };
        fetch('/api/node/' + deleteNodeObj?.id, requestOptions)
            .then(response => response)
            .then((response) => {
                if (response.ok) {
                    setDeleteOpen(false)
                    setDeleteNodeObj(undefined)
                    setNodes((nds) => nds.filter((node) => node.id !== deleteNodeObj?.id));
                }
            });
    }

    

    interface NodeRequest {
        id: number;
        type: string;
        x: number;
        y: number;
        label: string;
        tor_id?: number;
        ts_id?: number;
    }

    function formatNodeData(newNode: NewNode) {
        let newNodeRequest: NodeRequest = {
            id: parseInt(newNode.id),
            type: newNode.type,
            x: newNode.position.x,
            y: newNode.position.y,
            label: newNode.data.label,
        }
        if (newNode.data.tor) {
            newNodeRequest.tor_id = parseInt(newNode.data.tor);
        }
        if (newNode.data.ts) {
            newNodeRequest.ts_id = parseInt(newNode.data.ts);
        }
        return newNodeRequest;
    }

    const onDrop = useCallback(
        (event: any) => {
            event.preventDefault();
            if (reactFlowWrapper.current !== null && reactFlowInstance !== undefined) {
                const reactFlowBounds = reactFlowWrapper.current.getBoundingClientRect();
                const type = event.dataTransfer.getData('application/reactflow');

                // check if the dropped element is valid
                if (typeof type === 'undefined' || !type) {
                    return;
                }

                const position = reactFlowInstance.project({
                    x: event.clientX - reactFlowBounds.left,
                    y: event.clientY - reactFlowBounds.top,
                });
                let newNode = {} as NewNode;
                if (type === 'rackNode') {
                    newNode = {
                        id: '',
                        type,
                        position,
                        data: { label: 'New Rack', tor: "", ts: "", onChange: onChange, delete: deleteNode },
                    };
                } else if (type === 'labelNode') {
                    newNode = {
                        id: '',
                        type,
                        position,
                        data: { label: 'New Label', onChange: onChange, delete: deleteNode },
                    };
                }

                const requestOptions = {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify(formatNodeData(newNode))
                };
                fetch('/api/node', requestOptions)
                    .then(response => response)
                    .then((response) => {
                        if (response.ok) {
                            let json = response.json()
                            json.then((data) => {
                                newNode.id = data.id.toString();
                                console.log(data)
                                setNodes((ns) => ns.concat(newNode));
                            })
                        }
                    });
            }
        },
        [reactFlowInstance]
    );

    return (
        <>
            <ThemeProvider theme={flowTheme}>
                <div className="reactflow-wrapper h-full w-full" ref={reactFlowWrapper}>
                    <ReactFlow
                        nodes={nodes}
                        edges={edges}
                        onNodesChange={onNodesChange}
                        onEdgesChange={onEdgesChange}
                        onNodeDragStop={onNodeDragStop}
                        //onConnect={onConnect}
                        //style={{ background: bgColor }}
                        nodeTypes={nodeTypes}
                        //connectionLineStyle={connectionLineStyle}
                        snapToGrid={true}
                        snapGrid={[40, 40]}
                        attributionPosition="bottom-left"
                        onInit={setReactFlowInstance}
                        onDrop={onDrop}
                        onDragOver={onDragOver}
                    >
                        <ControlsStyled>
                            <Drag />
                        </ControlsStyled>
                        <Background color={resolvedTheme === 'dark' ? "#fff" : "#000"} gap={40} />
                    </ReactFlow>
                </div>
            </ThemeProvider>
            <DeleteModal isOpen={deleteOpen} close={() => setDeleteOpen(false)} confirm={deleteRequest} node={deleteNodeObj}/>
        </>
    )
}
export default Flow;