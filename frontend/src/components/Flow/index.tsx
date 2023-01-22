import React, { useState, useEffect, useCallback, useRef } from 'react';
import ReactFlow, { useNodesState, useEdgesState, addEdge, MiniMap, Controls, ReactFlowProps, Background, ReactFlowActions, ReactFlowRefType, ReactFlowInstance } from 'reactflow';
import styled, { ThemeProvider } from 'styled-components';
import { darkTheme, lightTheme } from './Theme';

import 'reactflow/dist/style.css';
import { useTheme } from 'next-themes';
import Drag from './Drag';
import RackNode from './RackNode';
import LabelNode from './LabelNode';
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
    const [updateNode, setUpdateNode] = useState<UpdateNode>();


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
                        label: event.target.value,
                    },
                };
            })
        );
    };

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
                const newNode = {
                    id: getId(),
                    type,
                    position,
                    data: { label: 'test', onChange: onChange },
                };

                setNodes((nds) => nds.concat(newNode));
            }
        },
        [reactFlowInstance]
    );

    useEffect(() => {
        setNodes((nds) =>
            nds.map((node) => {
                if (node.id === updateNode?.id.toString()) {
                    node.data = {
                        ...node.data,
                        label: updateNode?.data.label,
                    };
                }

                return node;
            })
        );
    }, [updateNode, setNodes]);

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
            <input onChange={(e) => setUpdateNode({ id: 'dndnode_0', data: { label: e.target.value } })} value={updateNode?.data.label} />
        </ThemeProvider>
    )
}
export default Flow;