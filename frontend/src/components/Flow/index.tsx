import React, { useState, useEffect, useCallback, useRef } from 'react';
import ReactFlow, { useNodesState, useEdgesState, addEdge, MiniMap, Controls, ReactFlowProps, Background, ReactFlowActions, ReactFlowRefType, ReactFlowInstance } from 'reactflow';
import styled, { ThemeProvider } from 'styled-components';
import { darkTheme, lightTheme } from './Theme';

import 'reactflow/dist/style.css';
import { useTheme } from 'next-themes';
import Drag from './Drag';
import RackNode from './RackNode';
import LabelNode from './LabelNode';
import { DeleteModal } from '../DeleteModal';
interface ControlsProps {
    theme: Theme
}
interface Theme {
    controlsBg: string;
    controlsColor: string;
    controlsBorder: string;
    controlsBgHover: string;
}
interface UpdateNode {
    id: string;
    data: NodeData;
}
interface NodeData {
    label: string;
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

let id = 0;
const getId = () => `dndnode_${id++}`;

const nodeTypes = {
    rackNode: RackNode,
    labelNode: LabelNode,
};

const Flow = () => {
    const { resolvedTheme } = useTheme()
    const flowTheme = resolvedTheme === 'light' ? lightTheme : darkTheme;
    const [nodes, setNodes, onNodesChange] = useNodesState([]);
    const [edges, setEdges, onEdgesChange] = useEdgesState([]);
    const [reactFlowInstance, setReactFlowInstance] = useState<ReactFlowInstance>();
    const reactFlowWrapper = useRef<ReactFlowRefType>(null);

    const [deleteModal, setDeleteModal] = useState<UpdateNode>();


    const onDragOver = useCallback((event: any) => {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
    }, []);

    const onChange = (event: any, id: string) => {
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
    };

    const deleteNode = (id: string) => {
        setNodes((nds) => nds.filter((node) => node.id !== id));
    };

    interface NewNode {
        id: string;
        type: string;
        position: { x: number; y: number };
        data: { label: string; tor?: string; ts?: string; onChange: (event: any, id: string) => void; delete: (id: string) => void };
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
                        id: getId(),
                        type,
                        position,
                        data: { label: 'New Rack', tor: "", ts: "", onChange: onChange, delete: deleteNode },
                    };
                } else if (type === 'labelNode') {
                    newNode = {
                        id: getId(),
                        type,
                        position,
                        data: { label: 'New Label', onChange: onChange, delete: deleteNode },
                    };
                }

                setNodes((nds) => nds.concat(newNode));
            }
        },
        [reactFlowInstance]
    );

    return (
        <ThemeProvider theme={flowTheme}>
            <div className="reactflow-wrapper h-full w-full" ref={reactFlowWrapper}>
                <ReactFlow
                    nodes={nodes}
                    edges={edges}
                    onNodesChange={onNodesChange}
                    onEdgesChange={onEdgesChange}
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
    )
}
export default Flow;